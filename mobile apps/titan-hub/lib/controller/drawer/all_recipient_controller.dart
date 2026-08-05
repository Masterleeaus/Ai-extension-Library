import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../backend/model/common/common_success_model.dart';
import '../../backend/model/recipient_models/all_recipient_model.dart';
import '../../backend/model/recipient_models/recipient_edit_model.dart';
import '../../backend/services/api_services.dart';
import '../../backend/utils/logger.dart';
import '../../routes/routes.dart';
import '../categories/remittance/edit_recipient_controller.dart';

final log = logger(AllRecipientController);

class AllRecipientController extends GetxController {
  @override
  void onInit() {
    getAllRecipientData();
    super.onInit();
  }

  final _isLoading = false.obs;
  bool get isLoading => _isLoading.value;

  late AllRecipientModel _allRecepientData;
  AllRecipientModel get allRecepientData => _allRecepientData;

  // --------------------------- Api function ----------------------------------
  // get bill pay data function
  Future<AllRecipientModel> getAllRecipientData() async {
    _isLoading.value = true;
    update();

    await ApiServices.allRecipientAPi().then((value) {
      _allRecepientData = value!;
      update();
    }).catchError((onError) {
      log.e(onError);
    });

    _isLoading.value = false;
    update();
    return _allRecepientData;
  }

  late CommonSuccessModel _successDatya;
  CommonSuccessModel get successDatya => _successDatya;

  Future<CommonSuccessModel> recipientDeleteApiProcess(
      {required String id}) async {
    _isLoading.value = true;
    update();

    Map<String, dynamic> inputBody = {
      'id': id,
    };
    // calling login api from api service
    await ApiServices.recipientDeleteApi(body: inputBody).then((value) {
      _successDatya = value!;
      // getAllRecipientData();

      getAllRecipientData();
      update();
      _isLoading.value = false;
      update();
    }).catchError((onError) {
      log.e(onError);
      _isLoading.value = false;
      update();
    });

    return _successDatya;
  }

  late RecipientEditModel _recipientEditData;
  RecipientEditModel get recipientEditData => _recipientEditData;

  Future<RecipientEditModel> recipientEditApiProcess(
      {required String id}) async {
    _isLoading.value = true;
    update();

    await ApiServices.recipientEditAPi(id: id).then((value) {
      _recipientEditData = value!;
      debugPrint("edit recipient");

      final controller = Get.put(EditRecipientController());

      controller.transactionTypeSelectedMethod.value =
          _recipientEditData.data.recipient.type;
      controller.receiverCountrySelectedMethodId.value =
          _recipientEditData.data.recipient.country;
      controller.receiverBankSelectedMethod.value =
          _recipientEditData.data.recipient.alias;
      controller.pickupPointMethod.value =
          _recipientEditData.data.recipient.alias;

      controller.accountNumberController.text =
          _recipientEditData.data.recipient.accountNumber;
      controller.emailAddressController.text =
          _recipientEditData.data.recipient.email;

      controller.firstNameController.text =
          _recipientEditData.data.recipient.firstname;
      controller.lastNameController.text =
          _recipientEditData.data.recipient.lastname;
      controller.addressController.text =
          _recipientEditData.data.recipient.address;
      controller.stateController.text = _recipientEditData.data.recipient.state;
      controller.cityController.text = _recipientEditData.data.recipient.city;
      controller.zipController.text = _recipientEditData.data.recipient.zipCode;
      controller.numberController.text =
          _recipientEditData.data.recipient.mobile;
      controller.updateUserId.value =
          _recipientEditData.data.recipient.id.toString();
      controller.basicController.countryCode.value =
          _recipientEditData.data.recipient.mobileCode;

      controller.getRecipientInfoData();

      Get.toNamed(Routes.editRecipientScreen);
      update();
    }).catchError((onError) {
      log.e(onError);
    });

    _isLoading.value = false;
    update();
    return _recipientEditData;
  }
}
